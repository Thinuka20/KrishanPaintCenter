<?php
require_once 'connection.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false];

if (isset($data['description']) && isset($data['price'])) {
    $description = $data['description'];
    $price = floatval($data['price']);
    
    $query = "INSERT INTO repair_items (description, price) VALUES 
             ('" . $description . "', " . $price . ")";
    Database::iud($query);
    $response['success'] = true;
}

echo json_encode($response);
?>