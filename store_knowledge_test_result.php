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
$knowledgeTestResult = $data['knowledge_test_result'] ?? null;

if (!$knowledgeTestResult) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

try {
    $pdo = getPDO();

    // Update the user's knowledge test result
    $stmt = $pdo->prepare("UPDATE waitlist_users SET knowledge_test_result = ? WHERE email = ?");
    $stmt->execute([json_encode($knowledgeTestResult), $email]);

    echo json_encode([
        'success' => true,
        'message' => 'Knowledge test result saved successfully',
        'result' => $knowledgeTestResult
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>