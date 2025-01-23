<?php
require_once 'connection.php';
session_start();
Database::connection();

try {
    Database::$connection->begin_transaction();

    $invoice_id = $_POST['invoice_id'];
    $vehicle_id = $_POST['vehicle_id'];

    // Update invoice
    $total_amount = 0;
    foreach ($_POST['items']['price'] as $price) {
        $total_amount += floatval($price);
    }

    $query = "UPDATE repair_invoices 
              SET vehicle_id = '$vehicle_id', total_amount = '$total_amount' 
              WHERE id = '$invoice_id'";
    Database::iud($query);

    // Delete existing items
    $query = "DELETE FROM repair_invoice_items WHERE repair_invoice_id = '$invoice_id'";
    Database::iud($query);

    // Insert updated items
    foreach ($_POST['items']['description'] as $key => $description) {
        $price = $_POST['items']['price'][$key];
        $description = Database::$connection->real_escape_string($description);

        $query = "INSERT INTO repair_invoice_items (repair_invoice_id, description, price) 
                  VALUES ('$invoice_id', '$description', '$price')";
        Database::iud($query);
    }

    // Handle new photos
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

    Database::$connection->commit();
    $_SESSION['success'] = "Invoice updated successfully";
} catch (Exception $e) {
    Database::$connection->rollback();
    $_SESSION['error'] = "Error updating invoice: " . $e->getMessage();
}

header("Location: view_repair_invoice.php?id=$invoice_id");
