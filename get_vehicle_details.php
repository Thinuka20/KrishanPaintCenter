<?php
// get_vehicle_details.php
require_once 'connection.php';

header('Content-Type: application/json');

$vehicle_id = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
$response = ['success' => false];

if ($vehicle_id) {
    $query = "SELECT v.*, c.name as customer_name 
              FROM vehicles v 
              LEFT JOIN customers c ON v.customer_id = c.id 
              WHERE v.id = " . $vehicle_id;
    $result = Database::search($query);
    
    if ($vehicle = $result->fetch_assoc()) {
        $response = [
            'success' => true,
            'vehicle' => $vehicle
        ];
    }
}

echo json_encode($response);

?>