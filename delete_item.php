<?php
// delete_item.php - Delete item
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$item_id = (int)$_GET['id'];

// Ensure database connection is established
Database::connection();

try {
    // Start transaction
    Database::$connection->begin_transaction();

    // Check if item is used in any invoices
    $query = "SELECT COUNT(*) as count FROM repair_invoice_items WHERE item_id = $item_id";
    $result = Database::search($query);
    $repair_count = $result->fetch_assoc()['count'];

    $query = "SELECT COUNT(*) as count FROM item_invoice_details WHERE item_id = $item_id";
    $result = Database::search($query);
    $item_count = $result->fetch_assoc()['count'];

    $query = "SELECT COUNT(*) as count FROM estimate_items WHERE item_id = $item_id";
    $result = Database::search($query);
    $estimate_count = $result->fetch_assoc()['count'];

    if ($repair_count > 0 || $item_count > 0 || $estimate_count > 0) {
        $_SESSION['error'] = "Cannot delete this item as it is used in invoices or estimates. Consider marking it as inactive instead.";
        header("Location: items.php");
        exit();
    }

    // Delete stock movements if the table exists
    $query = "SHOW TABLES LIKE 'stock_movements'";
    $result = Database::search($query);
    if ($result->num_rows > 0) {
        $query = "DELETE FROM stock_movements WHERE item_id = $item_id";
        Database::iud($query);
    }

    // Delete the item
    $query = "DELETE FROM items WHERE id = $item_id";
    Database::iud($query);

    Database::$connection->commit();
    $_SESSION['success'] = "Item deleted successfully.";
    header("Location: items.php");
    exit();
    
} catch (Exception $e) {
    if (isset(Database::$connection)) {
        Database::$connection->rollback();
    }
    $_SESSION['error'] = "Error deleting item: " . $e->getMessage();
    header("Location: items.php");
    exit();
}
?>