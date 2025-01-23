<?php
require_once 'connection.php';
session_start();

$photo_id = (int)$_GET['id'];

$query = "SELECT * FROM repair_photos WHERE id = $photo_id";
$result = Database::search($query);
$photo = $result->fetch_assoc();

if($photo) {
    $file_path = $photo['photo_path'];
    if(file_exists($file_path)) {
        unlink($file_path);
    }
    
    $query = "DELETE FROM repair_photos WHERE id = $photo_id";
    Database::iud($query);
    
    $_SESSION['success'] = "Photo deleted successfully";
} else {
    $_SESSION['error'] = "Photo not found";
}

header("Location: edit_repair_invoice.php?id=" . $photo['repair_invoice_id']);

?>