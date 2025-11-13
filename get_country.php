<?php
header('Content-Type: application/json');

// On live server, get real visitor IP
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];


$url = "https://ipapi.co/{$ip}/json/";

$response = @file_get_contents($url);

if ($response === FALSE) {
    echo json_encode(['country_name' => 'Unknown', 'country_code' => '']);
} else {
    echo $response;
}
