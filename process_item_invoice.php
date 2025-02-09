<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'] ?? null;
    $payment_type = $_POST['payment_type'] ?? null;
    $payment_amount = $_POST['payment_amount'] ?? 0;
    $cart_items = json_decode($_POST['cart_items'], true);
    $total_amount = $_POST['total_amount'] ?? 0;

    if (!$customer_id || empty($cart_items)) {
        header('Location: add_item_invoice.php');
        exit;
    }


    try {
        // Generate invoice number (format: II[YYYYMMDD][NNN])
        $date = date('Ymd');
        $query = "SELECT MAX(CAST(SUBSTRING(invoice_number, 11) AS SIGNED)) as max_num 
                 FROM item_invoices 
                 WHERE invoice_number LIKE 'II{$date}%'";
        $result = Database::search($query);
        $row = $result->fetch_assoc();
        $next_num = str_pad(($row['max_num'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
        $invoice_number = "II{$date}{$next_num}";

        // Determine payment status
        $payment_status = 'pending';
        if ($payment_amount >= $total_amount) {
            $payment_status = 'paid';
        } else if ($payment_amount > 0) {
            $payment_status = 'partial';
        }

        // Create invoice
        $query = "INSERT INTO item_invoices (customer_id, invoice_number, total_amount, 
                 payment_status, invoice_date) VALUES (?, ?, ?, ?, CURDATE())";
        $stmt = Database::$connection->prepare($query);
        $stmt->bind_param("isds", $customer_id, $invoice_number, $total_amount, $payment_status);
        $stmt->execute();
        $invoice_id = Database::$connection->insert_id;

        // Add invoice details and update stock
        foreach ($cart_items as $item) {
            // Add invoice detail
            $query = "INSERT INTO item_invoice_details (item_invoice_id, item_id, quantity, 
                     unit_price, subtotal) VALUES (?, ?, ?, ?, ?)";
            $stmt = Database::$connection->prepare($query);
            $subtotal = $item['quantity'] * $item['unit_price'];
            $stmt->bind_param("iiidi", $invoice_id, $item['item_id'], 
                            $item['quantity'], $item['unit_price'], $subtotal);
            $stmt->execute();

            // Update stock quantity
            $query = "UPDATE items SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $stmt = Database::$connection->prepare($query);
            $stmt->bind_param("ii", $item['quantity'], $item['item_id']);
            $stmt->execute();

            // Record stock movement
            $query = "INSERT INTO stock_movements (item_id, movement_type, quantity, reference) 
                     VALUES (?, 'out', ?, ?)";
            $stmt = Database::$connection->prepare($query);
            $reference = "Invoice #{$invoice_number}";
            $stmt->bind_param("iis", $item['item_id'], $item['quantity'], $reference);
            $stmt->execute();
        }

        // Record payment if any
        if ($payment_amount > 0) {
            $query = "INSERT INTO payment_transactions (invoice_type, invoice_id, amount, 
                     payment_type, payment_date) VALUES ('item', ?, ?, ?, CURDATE())";
            $stmt = Database::$connection->prepare($query);
            $stmt->bind_param("ids", $invoice_id, $payment_amount, $payment_type);
            $stmt->execute();
        }

        Database::$connection->commit();
        header("Location: view_item_invoice.php?id={$invoice_id}");
        exit;

    } catch (Exception $e) {
        Database::$connection->rollback();
        header('Location: add_item_invoice.php');
        exit;
    }
}

header('Location: add_item_invoice.php');
exit;
?>