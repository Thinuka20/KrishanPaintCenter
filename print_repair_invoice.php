<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
require_once 'classes/ReportPDFrepair.php';

checkLogin();

$invoice_id = (int)$_GET['id'];

if (!$invoice_id) {
    exit('Invalid request');
}

// Get invoice data
$query = "SELECT ri.*, v.registration_number, v.make, v.model, v.year,
          c.name as customer_name, c.phone, c.email, c.address 
          FROM repair_invoices ri
          LEFT JOIN vehicles v ON ri.vehicle_id = v.id
          LEFT JOIN customers c ON v.customer_id = c.id
          WHERE ri.id = $invoice_id";
$result = Database::search($query);
$invoice = $result->fetch_assoc();

if (!$invoice) {
    exit('Invoice not found');
}

// Get repair invoice items
$items_query = "SELECT description, price as amount 
                FROM repair_invoice_items 
                WHERE repair_invoice_id = $invoice_id";
$items_result = Database::search($items_query);
$items = array();
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

// Get payment transactions
$payments_query = "SELECT * FROM payment_transactions 
                  WHERE invoice_type = 'repair' 
                  AND invoice_id = $invoice_id 
                  ORDER BY payment_date";
$payments_result = Database::search($payments_query);
$payments = array();
while ($payment = $payments_result->fetch_assoc()) {
    $payments[] = $payment;
}

// Generate PDF
$pdf = new ReportPDFrepair('P', 'Repair Invoice');
$pdf->generateRepairInvoice($invoice, $items, $payments);
$pdf->Output('Repair_Invoice_' . $invoice['invoice_number'] . '.pdf', 'I');