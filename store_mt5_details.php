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

    // Insert MT5 details
    $stmt = $pdo->prepare("INSERT INTO mt5_details (user_id, username, password, server, instrument) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $mt5Details['username'], $mt5Details['password'], $mt5Details['server'], $mt5Details['instrument']]);

    echo json_encode([
        'success' => true,
        'message' => 'MT5 details saved successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>