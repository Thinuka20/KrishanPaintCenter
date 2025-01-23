<?php

// delete_item_invoice.php - Delete item invoice
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$invoice_id = (int)$_GET['id'];

try {
    // Get invoice items to restore stock
    $query = "SELECT * FROM item_invoice_details WHERE item_invoice_id = $invoice_id";
    $result = Database::search($query);
    while ($item = $result->fetch_assoc()) {
        // Restore stock
        $query = "UPDATE items 
                  SET stock_quantity = stock_quantity + {$item['quantity']} 
                  WHERE id = {$item['item_id']}";
        Database::iud($query);
    }

    // Delete invoice items
    $query = "DELETE FROM item_invoice_details WHERE item_invoice_id = $invoice_id";
    Database::iud($query);

    // Delete payment transactions
    $query = "DELETE FROM payment_transactions 
              WHERE invoice_type = 'item' AND invoice_id = $invoice_id";
    Database::iud($query);

    // Delete invoice
    $query = "DELETE FROM item_invoices WHERE id = $invoice_id";
    Database::iud($query);

    Database::$connection->commit();
    header("Location: invoices.php?type=item");
    exit();
} catch (Exception $e) {
    Database::$connection->rollback();
    die("Error deleting invoice: " . $e->getMessage());
}
