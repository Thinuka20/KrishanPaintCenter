<?php

session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false];

if (isset($data['index']) && isset($_SESSION['cart'][$data['index']])) {
    array_splice($_SESSION['cart'], $data['index'], 1);
    $response['success'] = true;
}

echo json_encode($response);

?>