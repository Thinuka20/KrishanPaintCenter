<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $theme = $_POST['theme'] ?? 'light';
        
        $query = "UPDATE settings SET theme = '$theme'";
            
        Database::iud($query);
        echo json_encode(['success' => true]);        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
