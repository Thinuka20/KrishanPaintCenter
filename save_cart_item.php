<?php
// save_cart_item.php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false];

if (isset($data['description']) && isset($data['price'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $_SESSION['cart'][] = $data;
    $response['success'] = true;
}

echo json_encode($response);

?>