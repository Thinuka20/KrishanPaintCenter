<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: invoices.php?type=item');
    exit();
}

$invoice_id = (int)$_POST['invoice_id'];
$customer_id = (int)$_POST['customer_id'];
$invoice_date = validateInput($_POST['invoice_date']);
$notes = validateInput($_POST['notes']);
$total_amount = 0;

// Decode cart items from JSON
$cart_items = json_decode($_POST['cart_items'], true);
if (!$cart_items) {
    $_SESSION['error_message'] = "Error: Invalid cart data";
    header("Location: edit_item_invoice.php?id=$invoice_id");
    exit();
}

// Start transaction
Database::connection();
Database::$connection->begin_transaction();

try {

    $invoicenum = "SELECT invoice_number FROM item_invoices WHERE id = $invoice_id";
    $invoicenum2 = Database::search($invoicenum);
    $invoicedata = $invoicenum2->fetch_assoc();
    $invoicenumber = $invoicedata['invoice_number'];
    // First, get existing items to calculate stock adjustments
    $query = "SELECT item_id, quantity FROM item_invoice_details WHERE item_invoice_id = $invoice_id";
    $existing_items = Database::search($query);
    $old_quantities = [];
    while ($row = $existing_items->fetch_assoc()) {
        $old_quantities[$row['item_id']] = $row['quantity'];
    }

    // Update invoice header
    $query = "UPDATE item_invoices SET 
              customer_id = $customer_id,
              invoice_date = '$invoice_date',
              notes = '$notes'
              WHERE id = $invoice_id";
    Database::iud($query);

    // Delete existing invoice items
    $query = "DELETE FROM item_invoice_details WHERE item_invoice_id = $invoice_id";
    Database::iud($query);

    // Restore old quantities to stock and record movements
    foreach ($old_quantities as $item_id => $quantity) {
        // Update stock quantity
        $query = "UPDATE items SET stock_quantity = stock_quantity + $quantity WHERE id = $item_id";
        Database::iud($query);

        // Record stock movement
        $query = "INSERT INTO stock_movements 
                  (item_id, movement_type, quantity, reference, movement_date) 
                  VALUES 
                  ($item_id, 'in', $quantity, 'Invoice #$invoicenumber update - restoring previous quantity', CURRENT_TIMESTAMP)";
        Database::iud($query);
    }

    // Insert updated invoice items
    $new_quantities = [];
    foreach ($cart_items as $item) {
        $item_id = (int)$item['item_id'];
        $quantity = (int)$item['quantity'];
        $unit_price = (float)$item['unit_price'];
        $subtotal = $quantity * $unit_price;
        $total_amount += $subtotal;

        // Store new quantities for stock update
        if (isset($new_quantities[$item_id])) {
            $new_quantities[$item_id] += $quantity;
        } else {
            $new_quantities[$item_id] = $quantity;
        }

        // Validate stock availability
        $query = "SELECT stock_quantity FROM items WHERE id = $item_id";
        $result = Database::search($query);
        $item_data = $result->fetch_assoc();
        
        $adjusted_stock = $item_data['stock_quantity'];
        if (isset($old_quantities[$item_id])) {
            $adjusted_stock += $old_quantities[$item_id];
        }
        
        if ($quantity > $adjusted_stock) {
            throw new Exception("Insufficient stock for item ID: $item_id");
        }

        // Insert invoice item
        $query = "INSERT INTO item_invoice_details (item_invoice_id, item_id, quantity, unit_price, subtotal) 
                  VALUES ($invoice_id, $item_id, $quantity, $unit_price, $subtotal)";
        Database::iud($query);
    }

    // Update stock quantities for new items and record movements
    foreach ($new_quantities as $item_id => $quantity) {
        // Update stock quantity
        $query = "UPDATE items SET stock_quantity = stock_quantity - $quantity WHERE id = $item_id";
        Database::iud($query);

        // Record stock movement
        $query = "INSERT INTO stock_movements 
                  (item_id, movement_type, quantity, reference, movement_date) 
                  VALUES 
                  ($item_id, 'out', $quantity, 'Invoice #$invoicenumber update', CURRENT_TIMESTAMP)";
        Database::iud($query);
    }

    // Update invoice total
    $query = "UPDATE item_invoices SET total_amount = $total_amount WHERE id = $invoice_id";
    Database::iud($query);

    // Commit transaction
    Database::$connection->commit();

    // Set success message and redirect
    $_SESSION['success_message'] = "Invoice updated successfully.";
    header("Location: view_item_invoice.php?id=$invoice_id");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    Database::$connection->rollback();
    
    // Set error message and redirect back to edit page
    $_SESSION['error_message'] = "Error updating invoice: " . $e->getMessage();
    header("Location: edit_item_invoice.php?id=$invoice_id");
    exit();
}
?>