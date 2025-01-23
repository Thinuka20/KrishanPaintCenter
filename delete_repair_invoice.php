<?php
// delete_repair_invoice.php - Delete repair invoice
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$invoice_id = (int)$_GET['id'];

// Start transaction

try {
    // Delete invoice items
    $query = "DELETE FROM repair_invoice_items WHERE repair_invoice_id = $invoice_id";
    Database::iud($query);
    
    // Delete payment transactions
    $query = "DELETE FROM payment_transactions 
              WHERE invoice_type = 'repair' AND invoice_id = $invoice_id";
    Database::iud($query);
    
    // Delete repair photos
    $query = "SELECT photo_path FROM repair_photos WHERE repair_invoice_id = $invoice_id";
    $result = Database::search($query);
    while ($photo = $result->fetch_assoc()) {
        if (file_exists($photo['photo_path'])) {
            unlink($photo['photo_path']);
        }
    }
    $query = "DELETE FROM repair_photos WHERE repair_invoice_id = $invoice_id";
    Database::iud($query);
    
    // Delete invoice
    $query = "DELETE FROM repair_invoices WHERE id = $invoice_id";
    Database::iud($query);
    
    Database::$connection->commit();
    header("Location: invoices.php?type=repair");
    exit();
    
} catch (Exception $e) {
    Database::$connection->rollback();
    die("Error deleting invoice: " . $e->getMessage());
}

?>