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

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = (int)$_POST['user_id'];
        $password = $_POST['password'];
        
        $query = "UPDATE users SET password = '$password' WHERE id = $user_id";
        Database::iud($query);
        
        echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>