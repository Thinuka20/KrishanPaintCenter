<?php

function generateUniqueNumber($prefix) {
    $date = date('Ymd');
    $query = "SELECT MAX(SUBSTRING(invoice_number, -3)) as last_num 
              FROM repair_invoices 
              WHERE invoice_number LIKE '$prefix$date%'";
    $result = Database::search($query);
    $row = $result->fetch_assoc();
    
    $next_num = str_pad(($row['last_num'] ? $row['last_num'] + 1 : 1), 3, '0', STR_PAD_LEFT);
    return $prefix . $date . $next_num;
}

function formatCurrency($amount) {
    return 'Rs. ' . number_format($amount, 2);
}

function uploadImage($file, $directory) {
    $target_dir = UPLOAD_PATH . $directory . '/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = date('YmdHis') . '.' . $file_extension;
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $directory . '/' . $new_filename;
    }
    return false;
}

function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatOTHours($hours) {
    $totalMinutes = $hours * 60;
    $hours = floor($totalMinutes / 60);
    $minutes = $totalMinutes % 60;
    return sprintf("%02d:%02d", $hours, $minutes);
}

?>