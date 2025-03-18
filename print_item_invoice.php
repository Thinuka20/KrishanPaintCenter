<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
require_once 'classes/ReportPDFinvoices.php';

checkLogin();

$invoice_id = (int)$_GET['id'];

if (!$invoice_id) {
    exit('Invalid request');
}

// Get invoice data
$query = "SELECT ii.*, c.name as customer_name, c.phone, c.email, c.address
          FROM item_invoices ii 
          LEFT JOIN customers c ON ii.customer_id = c.id 
          WHERE ii.id = $invoice_id";
$result = Database::search($query);
$invoice = $result->fetch_assoc();

if (!$invoice) {
    exit('Invoice not found');
}

// Get invoice items
$query = "SELECT iid.*, i.name as item_name, i.item_code 
          FROM item_invoice_details iid 
          LEFT JOIN items i ON iid.item_id = i.id 
          WHERE iid.item_invoice_id = $invoice_id";
$result = Database::search($query);
$items = array();
while ($item = $result->fetch_assoc()) {
    $items[] = $item;
}

// Get payment transactions
$query = "SELECT * FROM payment_transactions 
          WHERE invoice_type = 'item' AND invoice_id = $invoice_id 
          ORDER BY payment_date";
$result = Database::search($query);
$payments = array();
while ($payment = $result->fetch_assoc()) {
    $payments[] = $payment;
}

// Generate PDF
$pdf = new ReportPDFinvoices('P', 'Invoice');
$pdf->generateItemInvoice($invoice, $items, $payments);
$pdf->Output('Invoice_' . $invoice['invoice_number'] . '.pdf', 'I');