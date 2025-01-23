<?php
require_once 'connection.php';
require_once 'functions.php';
Database::connection(); // Initialize connection before using transactions
session_start();

try {
    Database::$connection->begin_transaction();

    $vehicle_id = $_POST['vehicle_id'];

    $invoice_number = generateUniqueNumber("RI");
    $total_amount = $_POST['total_amount'];
    $cart_items = json_decode($_POST['cart_items'], true);

    // Create invoice
    $query = "INSERT INTO repair_invoices (vehicle_id, invoice_number, total_amount, payment_status, invoice_date) 
              VALUES ('$vehicle_id', '$invoice_number', '$total_amount', 'pending', CURDATE())";
    Database::iud($query);
    $invoice_id = Database::$connection->insert_id;

    if (!empty($_FILES['repair_photos']['name'][0])) {
        $uploadDir = 'uploads/repairs/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['repair_photos']['tmp_name'] as $key => $tmp_name) {
            $extension = pathinfo($_FILES['repair_photos']['name'][$key], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $extension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($tmp_name, $filePath)) {
                $query = "INSERT INTO repair_photos (vehicle_id, repair_invoice_id, photo_path) 
                          VALUES ('$vehicle_id', '$invoice_id', '$filePath')";
                Database::iud($query);
            }
        }
    }

    // Insert repair items
    foreach ($cart_items as $item) {
        $description = Database::$connection->real_escape_string($item['description']);
        $price = $item['price'];

        $query = "INSERT INTO repair_invoice_items (repair_invoice_id, description, price) 
                  VALUES ('$invoice_id', '$description', '$price')";
        Database::iud($query);
    }

    // Process payment
    if (!empty($_POST['payment_amount'])) {
        $amount = $_POST['payment_amount'];
        $payment_type = $_POST['payment_type'];
        $query = "INSERT INTO payment_transactions (invoice_type, invoice_id, amount, payment_date, payment_type) 
                  VALUES ('repair', '$invoice_id', '$amount', CURDATE(), '$payment_type')";
        Database::iud($query);

        $status = ($amount >= $total_amount) ? 'paid' : 'partial';
        $query = "UPDATE repair_invoices SET payment_status = '$status' WHERE id = '$invoice_id'";
        Database::iud($query);
    }

    Database::$connection->commit();
    $_SESSION['success'] = "Invoice #$invoice_number created successfully";
    header("Location: view_repair_invoice.php?id=$invoice_id");
} catch (Exception $e) {
    Database::$connection->rollback();
    $_SESSION['error'] = "Error creating invoice: " . $e->getMessage();
    header("Location: repair_invoice.php");
}
