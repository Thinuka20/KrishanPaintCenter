<?php
require_once 'Database.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false];

if (isset($data['vehicle_id']) && isset($data['items'])) {
    $vehicle_id = (int)$data['vehicle_id'];
    $payment_type = $data['payment_type'];
    $total_amount = floatval($data['total_amount']);
    
    // Create invoice
    $query = "INSERT INTO invoices (vehicle_id, payment_type, total_amount, created_at) VALUES 
             (" . $vehicle_id . ", '" . $payment_type . "', " . $total_amount . ", NOW())";
    Database::iud($query);
    
    // Get last insert id
    $invoice_id = Database::search("SELECT LAST_INSERT_ID() as id")->fetch_assoc()['id'];
    
    // Insert items
    foreach ($data['items'] as $item) {
        $query = "INSERT INTO invoice_items (invoice_id, description, price) VALUES 
                 (" . $invoice_id . ", '" . $item['description'] . "', " . floatval($item['price']) . ")";
        Database::iud($query);
    }
    
    $response['success'] = true;
    $response['invoice_id'] = $invoice_id;
}

echo json_encode($response);
?>