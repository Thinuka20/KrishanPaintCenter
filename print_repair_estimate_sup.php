<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
require_once 'classes/PDFEstimatesnew.php';

checkLogin();

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    exit('Invalid request');
}

// Get supplementary estimate data
$query = "SELECT e.*, v.registration_number, v.make, v.model, v.year, 
          c.name as customer_name, c.phone, c.email, c.address 
          FROM estimates_supplimentary e
          JOIN vehicles v ON e.vehicle_id = v.id 
          JOIN customers c ON v.customer_id = c.id
          WHERE e.id = '$id'";
$result = Database::search($query);
$estimate = $result->fetch_assoc();

// Get estimate items
$items_query = "SELECT * FROM estimate_items_supplimentary WHERE estimate_id = '$id'";
$items_result = Database::search($items_query);

$items = array();
$totals = array('total_amount' => 0);

while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
    $totals['total_amount'] += $item['price'];
}

// Generate PDF
$pdf = new ReportPDF('P', 'Supplementary Estimate');
$pdf->generateSupplementaryEstimate($estimate, $items, $totals);
$pdf->Output('Supplementary_Estimate_' . $estimate['estimate_number'] . '.pdf', 'I');
?>