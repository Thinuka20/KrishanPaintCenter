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
        
        // Check if user is trying to delete themselves
        if ($user_id == $_SESSION['user_id']) {
            throw new Exception("You cannot delete your own account");
        }
        
        $query = "DELETE FROM users WHERE id = $user_id";
        Database::iud($query);
        
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>