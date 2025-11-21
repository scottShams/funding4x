<?php
session_start();
require_once 'database.php';
$pdo = getPDO();

$email = $_SESSION['user_email'];
if(empty($email)){
    echo json_encode(["status" => "error", "message" => "No session email"]);
    exit;
}

// Read JSON from request
$data = json_decode(file_get_contents("php://input"), true);

if(isset($data['quiz_result'])){
    $quiz_result_json = json_encode($data['quiz_result']);
    $stmt = $pdo->prepare("UPDATE waitlist_users SET quiz_result = ? WHERE email = ?");
    $stmt->execute([$quiz_result_json, $email]);

    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error","message"=>"No quiz_result sent"]);
}
