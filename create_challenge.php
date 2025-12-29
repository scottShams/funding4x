<?php
session_start();
require_once 'database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$pdo = getPDO();
$userId = $_SESSION['user_id'];
try {
    // --- CREDIT CHECK: ensure user has at least 1 credit before creating a free challenge ---
    $credStmt = $pdo->prepare("SELECT user_credit FROM waitlist_users WHERE id = ?");
    $credStmt->execute([$userId]);
    $uRow = $credStmt->fetch(PDO::FETCH_ASSOC);
    $userCredit = isset($uRow['user_credit']) ? (int)$uRow['user_credit'] : 0;

    if ($userCredit < 1) {
        // If AJAX request, return a JSON response with a redirect target
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Insufficient credits to create a challenge', 'redirect' => 'checkout.php']);
            exit;
        }
        // Non-AJAX: redirect to checkout
        header('Location: checkout.php');
        exit;
    }

    // Determine next challenge number
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM challenges WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = (int)($row['c'] ?? 0);
    $challengeNumber = $count + 1;
    $challengeName = 'Challenge ' . $challengeNumber;

    // Insert challenge only
    $insert = $pdo->prepare("INSERT INTO challenges (user_id, challenge_number, challenge_name, status) VALUES (?, ?, ?, 'pending')");
    $insert->execute([$userId, $challengeNumber, $challengeName]);
    $challengeId = (int)$pdo->lastInsertId();

    $updateCredit = $pdo->prepare("UPDATE waitlist_users SET user_credit = ? WHERE id = ?");
    $updateCredit->execute([0, $userId]);

    // Fetch the created challenge
    $stmtC = $pdo->prepare("SELECT id, user_id, challenge_number, challenge_name, status, created_at FROM challenges WHERE id = ?");
    $stmtC->execute([$challengeId]);
    $challengeRow = $stmtC->fetch(PDO::FETCH_ASSOC);

    // For AJAX responses, we can indicate that phase records do not yet exist
    $p1Row = null;
    $p2Row = null;

    // If AJAX request, return JSON including the created rows
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode([
            'success' => true,
            'challenge' => $challengeRow,
            'phase1' => $p1Row,
            'phase2' => $p2Row
        ]);
        exit;
    }

    // Otherwise redirect back to dashboard anchored to new challenge
    header('Location: my-challenges.php#challenge-' . $challengeId);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
