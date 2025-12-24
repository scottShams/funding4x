<?php
session_start();
require_once 'database.php';
header('Content-Type: application/json');

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;
    $code = trim($data['promo_code'] ?? '');
    if ($code === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Promo code is required']);
        exit;
    }

    $pdo = getPDO();
    // Find promo code (case-insensitive)
    $stmt = $pdo->prepare('SELECT * FROM promo_codes WHERE LOWER(code) = LOWER(?) LIMIT 1');
    $stmt->execute([$code]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promo) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Promo code not found']);
        exit;
    }

    // Validate expiration
    if (!empty($promo['expires_at']) && strtotime($promo['expires_at']) < time()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Promo code expired']);
        exit;
    }

    // Validate max uses
    if (!is_null($promo['max_uses']) && (int)$promo['uses'] >= (int)$promo['max_uses']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Promo code usage limit reached']);
        exit;
    }

    // Determine current checkout price
    $currentPrice = 59; // default
    if (isset($_SESSION['checkout_price'])) {
        $currentPrice = (float)$_SESSION['checkout_price'];
    } elseif (isset($_COOKIE['checkout_price'])) {
        $currentPrice = (float)$_COOKIE['checkout_price'];
    }

    if ($promo['type'] === 'percent') {
        $discountAmount = round($currentPrice * ((float)$promo['value'] / 100.0), 2);
    } else {
        $discountAmount = round((float)$promo['value'], 2);
    }
    $final = max(round($currentPrice - $discountAmount, 2), 0.00);

    // Store applied promo into session for later processing
    $_SESSION['applied_promo'] = [
        'id' => (int)$promo['id'],
        'code' => $promo['code'],
        'type' => $promo['type'],
        'value' => (float)$promo['value'],
        'discount' => $discountAmount,
        'final_price' => $final,
        'applied_at' => date('c')
    ];

    echo json_encode(['success' => true, 'discount' => $discountAmount, 'final_price' => $final, 'promo' => ['code' => $promo['code'], 'type' => $promo['type'], 'value' => $promo['value']]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}
exit;