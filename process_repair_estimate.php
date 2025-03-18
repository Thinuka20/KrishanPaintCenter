<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['vehicle_id'])) {
    $_SESSION['error'] = 'Invalid request';
    header("Location: view_estimates_sup.php");
    exit();
}

$vehicle_id = intval($_POST['vehicle_id']);
$estimate_items = json_decode($_POST['estimate_items'] ?? '[]', true);
$total_amount = 0;
$notes = trim($_POST['notes'] ?? '');
$estimate_date = !empty($_POST['estimate_date']) ? $_POST['estimate_date'] : date('Y-m-d');

// Generate estimate number
$result = Database::search("SELECT COUNT(*) as count FROM estimates WHERE DATE(created_at) = CURDATE()");
$count = intval($result->fetch_assoc()['count']) + 1;
$estimate_number = sprintf("EST%s%03d", date('Ymd'), $count);

// Insert estimate
$safeNotes = Database::$connection->real_escape_string($notes);
$estimate_query = "INSERT INTO estimates (vehicle_id, estimate_number, total_amount, estimate_date, notes) 
                  VALUES ('$vehicle_id', '$estimate_number', '$total_amount', '$estimate_date', '$safeNotes')";
Database::iud($estimate_query);

$estimate_id = Database::$connection->insert_id;

// Insert items and calculate total
foreach ($estimate_items as $item) {
    $description = Database::$connection->real_escape_string($item['description']);
    $category = Database::$connection->real_escape_string($item['category']);
    $price = floatval($item['price']);
    
    $query = "INSERT INTO estimate_items (estimate_id, description, category, price) 
              VALUES ('$estimate_id', '$description', '$category', '$price')";
    Database::iud($query);
    
    $total_amount += $price;
}

// Update total amount
$update_total = "UPDATE estimates SET total_amount = '$total_amount' WHERE id = '$estimate_id'";
Database::iud($update_total);

$_SESSION['success'] = 'Estimate #' . $estimate_number . ' created successfully';
header("Location: view_repair_estimate.php?id=" . $estimate_id);
exit();
?>